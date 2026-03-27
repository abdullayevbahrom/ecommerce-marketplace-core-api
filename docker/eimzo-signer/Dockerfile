FROM amazoncorretto:8-alpine3.19 AS builder

WORKDIR /build

COPY eimzo-signer/SignerServer.java ./
COPY eimzo-server/lib ./lib

RUN javac -cp "./lib/*" SignerServer.java

FROM amazoncorretto:8-alpine3.19-jre

WORKDIR /opt/eimzo

COPY --from=builder /build/SignerServer*.class ./
COPY --from=builder /build/lib ./lib

EXPOSE 8080

HEALTHCHECK --interval=15s --timeout=5s --start-period=20s --retries=5 \
  CMD wget -qO- http://127.0.0.1:8080/health >/dev/null 2>&1 || exit 1

CMD ["java", "-cp", "/opt/eimzo:/opt/eimzo/lib/*", "SignerServer", "8080"]
